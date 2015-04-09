<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class BaseModel extends Eloquent implements UserInterface, RemindableInterface {

    use UserTrait, RemindableTrait;

    /**
     * The attributes that hold geometrical data.
     *
     * @var array
     */
    protected $geometry = array();

    /**
     * Select geometrical attributes as text from database.
     *
     * @var bool
     */
    protected $geometryAsText = false;

    /**
     * Get a new query builder for the model's table.
     * Manipulate in case we need to convert geometrical fields to text.
     *
     * @param  bool  $excludeDeleted
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery($excludeDeleted = true)
    {
        if (!empty($this->geometry) && $this->geometryAsText === true)
        {
            $raw = '';
            foreach ($this->geometry as $column)
            {
                $raw .= 'substring(AsText(`' . DB::getTablePrefix() . $this->table . '`.`' . $column . '`), 10, length(AsText(`geo_data`))-11) as `' . $column . '`, ';
            }
            $raw = substr($raw, 0, -2);
            return parent::newQuery($excludeDeleted)->addSelect('*', DB::raw($raw));
        }
        return parent::newQuery($excludeDeleted);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        // before save code
        $old_geometry=array();

        if (!empty($this->geometry) && $this->geometryAsText === true)
        {
            foreach ($this->geometry as $column)
            {
                $old_geometry[$column] = $this[$column];
                $this[$column] = DB::raw("PolygonFromText('POLYGON((".$this[$column]."))')");
            }
        }

        $results = parent::save( $options );

        // after
        if (!empty($this->geometry) && $this->geometryAsText === true)
        {
            foreach ($this->geometry as $column)
            {
                $this[$column] = $old_geometry[$column];
            }
        }
        return $results;
    }
}
